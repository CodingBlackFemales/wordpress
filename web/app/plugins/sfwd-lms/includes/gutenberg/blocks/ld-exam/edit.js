/**
 * LearnDash Block ld-exam Edit
 *
 * @since 4.0.0
 * @package LearnDash
 */

import { InnerBlocks, ButtonBlockAppender } from "@wordpress/block-editor";
import { useSelect } from "@wordpress/data";
import { useMemo } from "@wordpress/element";

/**
 * LearnDash block functions
 */
import { ExamContext } from "./exam-context";

const Edit = ( props ) => {
	const {
		attributes: { ld_version = "" },
		setAttributes,
		clientId
	} = props;

	const template = [
		[ "learndash/ld-exam-question", {} ],
	];

	const blockOrder = useSelect( ( select ) => {
        return select( "core/block-editor" ).getBlockOrder( clientId );
    }, [] );

	const examContext = useMemo(
		() => ( {
			blockOrder
		} ), [
			clientId,
			blockOrder
		]
	);

    if ( ld_version === "" ) {
		setAttributes( { ld_version: ldlms_settings.version } );
	}

	return <ExamContext.Provider value={ examContext }>
		<InnerBlocks
			allowedBlocks={ [ "learndash/ld-exam-question" ] }
			template={ template }
			renderAppender={ () =>
				<ButtonBlockAppender
					className="ld-exam-block-appender"
					rootClientId={ clientId }
				/>
			}
			templateInsertUpdatesSelection={ true }
		/>
	</ExamContext.Provider>
};

export default Edit;
