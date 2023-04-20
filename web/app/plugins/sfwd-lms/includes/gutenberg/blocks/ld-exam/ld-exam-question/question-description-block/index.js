/**
 * LearnDash Block ld-question-description
 *
 * @since 4.0.0
 * @package LearnDash
 */

import { __, _x, sprintf} from "@wordpress/i18n";
import { registerBlockType } from "@wordpress/blocks";
import { InnerBlocks } from "@wordpress/block-editor";
import { MdAssignment } from "react-icons/md";

/**
 * LearnDash block functions
 */
import { ldlms_get_custom_label } from "../../../ldlms.js";

const block_key = "learndash/ld-question-description";
const block_title = __( "Question Notes", "learndash" );
const block_description = sprintf(
	// translators: placeholder: Write a description for the Challenge Exam question.
	_x(
        "Write a description for the %s question.",
        "placeholder: Write a description for the Challenge Exam question",
        "learndash"
    ),
    ldlms_get_custom_label( "exam" )
);

registerBlockType( block_key, {
    title: block_title,
    description: block_description,
    icon: <MdAssignment />,
    parent: [ "learndash/ld-exam-question" ],
    category: "learndash-blocks",
    supports: {
        inserter: false,
        html: false
    },
    edit: () => {
        const template = [ [ "core/paragraph", {
            placeholder: __(
                "Add a Description or type '/' to choose a block (Optional)",
                "learndash"
            )
        } ] ];
        return (
            <InnerBlocks
                templateLock={ false }
                template={ template }
            />
        );
    },
    save: () => <InnerBlocks.Content />
} );
