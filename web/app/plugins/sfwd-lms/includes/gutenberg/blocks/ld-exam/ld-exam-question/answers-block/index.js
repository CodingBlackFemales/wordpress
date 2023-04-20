/**
 * LearnDash Block ld-question-answers-block
 *
 * @since 4.0.0
 * @package LearnDash
 */

import { __, _x, sprintf } from "@wordpress/i18n";
import { registerBlockType } from "@wordpress/blocks";
import { InnerBlocks } from "@wordpress/block-editor";
import { MdQuiz } from "react-icons/md";

/**
 * LearnDash block functions
 */
import { ldlms_get_custom_label } from "../../../ldlms.js";
import Edit from "./edit";

const block_key = "learndash/ld-question-answers-block";
const block_title = sprintf(
	// translators: placeholder: Challenge Exam Question Answers.
	_x( "%s Question Answers", "placeholder: Challenge Exam Question Answers", "learndash" ), ldlms_get_custom_label( "exam" )
);
const block_description = sprintf(
	// translators: placeholder: Challenge Exam Question Answers.
	_x( "%s Question Answers", "placeholder: Challenge Exam Question Answers", "learndash" ), ldlms_get_custom_label( "exam" )
);

registerBlockType( block_key, {
    title: block_title,
    description: block_description,
    icon: <MdQuiz />,
    category: "learndash-blocks",
    parent: [ "learndash/ld-exam-question" ],
    usesContext: [ "learndash/question_type" ],
    attributes: {
        question_type: {
            type: "string",
            default: ""
        },
        answers: {
            type: "array",
            default: []
        }
    },
    supports: {
        inserter: false,
        html: false
    },
    edit: Edit,
    save: () => <InnerBlocks.Content/>
} );
