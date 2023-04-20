/**
 * LearnDash Block ld-incorrect-answer-message-block ld-incorrect-answer-message-block
 *
 * @since 4.0.0
 * @package LearnDash
 */

import { __ } from "@wordpress/i18n";
import { registerBlockType } from "@wordpress/blocks";
import { InnerBlocks } from "@wordpress/block-editor";
import { Fragment } from "@wordpress/element";
import { MdQuiz } from "react-icons/md";

const settings = {
    icon: <MdQuiz />,
    parent: [ "learndash/ld-exam-question" ],
    category: "learndash-blocks",
    supports: {
        inserter: false,
        html: false
    },
    save: () => <InnerBlocks.Content />
};

const allowedBlocks = [
    'core/image',
    'core/paragraph',
];

export const IncorrectAnswerMessage = registerBlockType(
    "learndash/ld-incorrect-answer-message-block", {
        ...settings,
        title: __( "Incorrect answer message", "learndash" ),
        description: __( "Incorrect answer message", "learndash" ),
        edit: () => {
            const template = [ [ "core/paragraph", {
                placeholder: __( "Add a message for incorrect answer (Optional)", "learndash" )
            } ] ];
            return (
				<Fragment>
					<div>{ __( "Incorrect Answer Message", "learndash" ) }</div>
					<InnerBlocks
						allowedBlocks={ allowedBlocks }
						template={ template }
						templateLock={ false }
					/>
				</Fragment>
			)
        }
    }
);

export const CorrectAnswerMessage = registerBlockType(
    "learndash/ld-correct-answer-message-block", {
        ...settings,
        title: __( "Correct answer message", "learndash" ),
        description: __( "Correct answer message", "learndash" ),
        edit: () => {
            const template = [ [ "core/paragraph", {
                placeholder: __( "Add a message for correct answer (Optional)", "learndash" )
            } ] ];
            return  (
				<Fragment>
					<div>{ __( "Correct Answer Message", "learndash" ) }</div>
					<InnerBlocks
						allowedBlocks={ allowedBlocks }
						template={ template }
						templateLock={ false }
					/>
				</Fragment>
			)
        }
    }
);
