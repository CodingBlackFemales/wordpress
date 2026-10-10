/**
 * The Course Glossary block's editor UI.
 *
 * The preview is rendered by PHP (ServerSideRender), since which lessons count
 * is LearnDash's decision. In the editor every lesson counts, except that
 * dripped lessons appear as collapsed placeholders when "Preview locked
 * lessons" is on.
 */

/**
 * WordPress dependencies
 */
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { registerBlockType } from '@wordpress/blocks';
import * as components from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';
import ServerSideRender from '@wordpress/server-side-render';

const BLOCK_NAME = 'cbf/course-glossary';

const {
	Disabled,
	PanelBody,
	RangeControl,
	SelectControl,
	TextControl,
	ToggleControl,
} = components;

// WordPress 7.1 still ships these only under their experimental names; prefer
// the stable export once it exists.
const ToggleGroupControl =
	components.ToggleGroupControl ||
	components.__experimentalToggleGroupControl;
const ToggleGroupControlOption =
	components.ToggleGroupControlOption ||
	components.__experimentalToggleGroupControlOption;

/**
 * Settings printed by CourseGlossaryBlock::editor_config(), plus core's.
 */
const config = {
	courses: [],
	currentCourse: '',
	...( window.cbfCourseGlossary || {} ),
};

const siteIndexMin = window.cbfGlossary?.indexMinEntries ?? 8;

function Edit( { attributes, setAttributes } ) {
	const {
		course,
		heading,
		level,
		showFilter,
		showSource,
		showAlternatives,
		backLinks,
		showIndex,
		indexMinEntries,
		previewLocked,
	} = attributes;

	const toggle = ( key, label, help ) => (
		<ToggleControl
			__nextHasNoMarginBottom
			label={ label }
			help={ help }
			checked={ attributes[ key ] }
			onChange={ ( value ) => setAttributes( { [ key ]: value } ) }
		/>
	);

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __(
						'Settings',
						'cbf-semantic-glossary-learndash'
					) }
				>
					<SelectControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __(
							'Course',
							'cbf-semantic-glossary-learndash'
						) }
						help={ __(
							'"Current course" follows the course this page belongs to.',
							'cbf-semantic-glossary-learndash'
						) }
						value={ String( course ) }
						options={ [
							{
								value: '0',
								label: config.currentCourse
									? sprintf(
											/* translators: %s: course title */
											__(
												'Current course (%s)',
												'cbf-semantic-glossary-learndash'
											),
											config.currentCourse
										)
									: __(
											'Current course',
											'cbf-semantic-glossary-learndash'
										),
							},
							...config.courses.map( ( item ) => ( {
								value: String( item.id ),
								label: item.title,
							} ) ),
						] }
						onChange={ ( value ) =>
							setAttributes( { course: Number( value ) } )
						}
					/>
					<TextControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __(
							'Heading text',
							'cbf-semantic-glossary-learndash'
						) }
						value={ heading }
						onChange={ ( value ) =>
							setAttributes( { heading: value } )
						}
					/>
					<ToggleGroupControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						isBlock
						label={ __(
							'Heading level',
							'cbf-semantic-glossary-learndash'
						) }
						value={ level }
						onChange={ ( value ) =>
							setAttributes( { level: Number( value ) } )
						}
					>
						{ [ 2, 3, 4 ].map( ( option ) => (
							<ToggleGroupControlOption
								key={ option }
								value={ option }
								label={ `H${ option }` }
							/>
						) ) }
					</ToggleGroupControl>
					{ toggle(
						'showFilter',
						__(
							'Show lesson filter',
							'cbf-semantic-glossary-learndash'
						),
						__(
							'Shown when the learner has more than one lesson to choose from.',
							'cbf-semantic-glossary-learndash'
						)
					) }
					{ toggle(
						'showSource',
						__(
							'Show "Introduced in" line',
							'cbf-semantic-glossary-learndash'
						)
					) }
					{ toggle(
						'showAlternatives',
						__(
							'Show alternative terms',
							'cbf-semantic-glossary-learndash'
						)
					) }
					{ toggle(
						'backLinks',
						__(
							'Back-links to first mention',
							'cbf-semantic-glossary-learndash'
						),
						__(
							'Links each term to where its lesson first uses it.',
							'cbf-semantic-glossary-learndash'
						)
					) }
					{ toggle(
						'showIndex',
						__(
							'Show A–Z index',
							'cbf-semantic-glossary-learndash'
						),
						sprintf(
							/* translators: %d: minimum number of entries */
							__(
								'Shown once the glossary has at least %d entries.',
								'cbf-semantic-glossary-learndash'
							),
							indexMinEntries ?? siteIndexMin
						)
					) }
					{ showIndex && (
						<RangeControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={ __(
								'Minimum entries for the index',
								'cbf-semantic-glossary-learndash'
							) }
							help={
								indexMinEntries === undefined
									? sprintf(
											/* translators: %d: the site-wide default */
											__(
												'Using the site default (%d), set in Glossary › Settings.',
												'cbf-semantic-glossary-learndash'
											),
											siteIndexMin
										)
									: __(
											'Reset to use the site default.',
											'cbf-semantic-glossary-learndash'
										)
							}
							value={ indexMinEntries }
							initialPosition={ siteIndexMin }
							min={ 1 }
							max={ 50 }
							allowReset
							resetFallbackValue={ undefined }
							onChange={ ( value ) =>
								setAttributes( { indexMinEntries: value } )
							}
						/>
					) }
					{ toggle(
						'previewLocked',
						__(
							'Preview locked lessons in editor',
							'cbf-semantic-glossary-learndash'
						),
						__(
							'Shows dripped lessons as collapsed placeholders here. Learners never see them.',
							'cbf-semantic-glossary-learndash'
						)
					) }
				</PanelBody>
				<p className="cbf-glossary-inspector-note">
					{ __(
						'Visibility follows LearnDash: enrolment, drip schedule and linear progression decide which lessons count for each learner. Rendered per learner, so pages with this block are excluded from page caching.',
						'cbf-semantic-glossary-learndash'
					) }
				</p>
			</InspectorControls>

			<div { ...useBlockProps() }>
				<Disabled>
					<ServerSideRender
						block={ BLOCK_NAME }
						attributes={ {
							course,
							heading,
							level,
							showFilter,
							showSource,
							showAlternatives,
							backLinks,
							showIndex,
							indexMinEntries,
							previewLocked,
						} }
					/>
				</Disabled>
			</div>
		</>
	);
}

registerBlockType( BLOCK_NAME, {
	edit: Edit,
	save: () => null,
} );
