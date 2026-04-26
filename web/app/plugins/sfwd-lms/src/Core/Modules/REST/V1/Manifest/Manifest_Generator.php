<?php
/**
 * Takes the existing OpenAPI spec and modifies it for
 * use with the manifest endpoints.
 *
 * @since 5.0.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\REST\V1\Manifest;

use LearnDash\Core\Modules\REST\V1\Controller;
use StellarWP\Learndash\StellarWP\Arrays\Arr;

/**
 * Generate an LLM friendly JSON manifest.
 *
 * @since 5.0.0
 *
 * @phpstan-type OpenApiSpec array{
 *      openapi: string,
 *      info: array<string, mixed>,
 *      servers: list<array<string, mixed>>,
 *      paths: array{},
 *      components: array{schemas: array<string,array<string,mixed>>, securitySchemes: array<string,array<string,string>>}
 * }
 *
 * @phpstan-type ManifestArray array{
 *       manifest: array{
 *          title: string,
 *          desc: string,
 *          base_url: string,
 *          paths: array<string, array<string, array<string, mixed>>>
 *      },
 *      components: array{schemas: array<string,array<string,mixed>>, securitySchemes: array<string,array<string,string>>}
 * }
 */
class Manifest_Generator {
	/**
	 * The controller.
	 *
	 * @since 5.0.0
	 *
	 * @var Controller
	 */
	private Controller $controller;

	/**
	 * The ref replacer.
	 *
	 * @since 5.0.0
	 *
	 * @var Ref_Replacer
	 */
	private Ref_Replacer $replacer;

	/**
	 * The OpenAPI base spec.
	 *
	 * @var OpenApiSpec
	 */
	private array $spec;

	/**
	 * Constructor.
	 *
	 * @since 5.0.0
	 *
	 * @param Controller   $controller The controller.
	 * @param Ref_Replacer $replacer The $ref replacer.
	 * @param array        $spec The OpenAPI base spec.
	 *
	 * @phpstan-param OpenApiSpec $spec
	 */
	public function __construct(
		Controller $controller,
		Ref_Replacer $replacer,
		array $spec
	) {
		$this->controller = $controller;
		$this->spec       = $spec;
		$this->replacer   = $replacer;
	}

	/**
	 * Get the manifest data.
	 *
	 * @since 5.0.0
	 *
	 * @return ManifestArray
	 */
	public function get(): array {
		$documentation = $this->spec;

		foreach ( $this->controller->get_endpoints() as $endpoint ) {
			if ( ! method_exists( $endpoint, 'get_openapi_schema' ) ) {
				continue;
			}

			$documentation['paths'] = array_merge(
				Arr::wrap( $documentation['paths'] ),
				$endpoint->get_openapi_schema()
			);
		}

		$manifest = [
			'manifest'   => [
				'title'    => __( 'LearnDash REST API Manifest', 'learndash' ),
				'desc'     => __( 'API endpoint summary. Each endpoint has an \'href\' link to its complete specification. MANDATORY WORKFLOW: Before ANY API request (including GET), you MUST first GET the \'href\' URL to fetch the complete JSON schema (parameters, validation rules, response objects). The href shows exactly how to structure requests. Never guess at parameters - always fetch the href details first.', 'learndash' ),
				'base_url' => $this->spec['servers'][0]['url'],
				'paths'    => $documentation['paths'],
			],
			'components' => $documentation['components'],
		];

		/**
		 * Replace `#/components/schemas` with `/learndash/v1/manifest/component`.
		 *
		 * @var ManifestArray $replaced
		 */
		$replaced = $this->replacer->replace( $manifest );

		return $replaced;
	}
}
