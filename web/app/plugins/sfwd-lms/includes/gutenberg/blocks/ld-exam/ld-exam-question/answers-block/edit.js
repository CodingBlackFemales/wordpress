/**
 * LearnDash Block ld-question-answers-block Edit
 *
 * @since 4.0.0
 * @package LearnDash
 */

/**
 * LearnDash block functions
 */
import AnswerTypeBlock from "./question-types";

const Edit = ( props ) => {
    const {
        attributes: {
            answers
        },
        setAttributes,
        context,
        clientId
    } = props;

    const questionType = "learndash/question_type" in context  && context[ "learndash/question_type" ] ?
        context[ "learndash/question_type" ] :
        "single";

    const RenderBlock = AnswerTypeBlock[ questionType ];
    setAttributes( { question_type: questionType } );

    return <RenderBlock
                clientId={ clientId }
                type={ questionType }
                attributes={ [ ...answers ] }
                setAttributes={ ( newAnswers ) =>
                    setAttributes( { answers: [ ...newAnswers ] } )
                }
            />
}

export default Edit;
