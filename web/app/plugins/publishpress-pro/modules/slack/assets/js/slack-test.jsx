import {publishpressProCallAjaxPostAction} from "./Functions";

const $ = jQuery;

export default function SlackTest(props) {

    const REQUEST_STATE_IDLE = 'idle';
    const REQUEST_STATE_LOADING = 'loading';
    const REQUEST_STATE_SUCCESS = 'success';
    const REQUEST_STATE_ERROR = 'error';

    const [requestState, setRequestState] = React.useState(REQUEST_STATE_IDLE);
    const [resultMessage, setResultMessage] = React.useState('');

    const onTestButtonClick = function (event) {
        event.preventDefault();

        setResultMessage('');

        setRequestState(REQUEST_STATE_LOADING);

        let body = new FormData;

        body.append('_ajax_nonce', props.nonce);

        try {
            publishpressProCallAjaxPostAction(
                'publishpress_pro_send_slack_test_message',
                [],
                props.ajaxUrl,
                body
            ).then((result) => {
                if (result == -1) {
                    setRequestState(REQUEST_STATE_ERROR);
                    setResultMessage(props.text.nonceError);
                    return;
                }

                if (result.success) {
                    setRequestState(REQUEST_STATE_SUCCESS);
                    setResultMessage(props.text.msgSuccess.replace('%s', result.data.channel));
                } else {
                    setRequestState(REQUEST_STATE_ERROR);

                    if (typeof result.data === 'undefined' || result.data.message === null || result.data.message === null) {
                        setResultMessage(props.text.generalError);
                    } else {
                        let errorMessage = '';

                        for (let i = 0; i < result.data.length; i++) {
                            errorMessage += result.data[i].message + "\n";
                        }

                        if (errorMessage === '') {
                            errorMessage = props.text.generalError;
                        }

                        setResultMessage(errorMessage);
                    }
                }
            });
        } catch (e) {
            setRequestState(REQUEST_STATE_ERROR);
            setResultMessage(result);
        }
    }

    const buttonLabel = requestState === REQUEST_STATE_LOADING ? props.text.sendButtonLoading : props.text.sendButtonIdle;

    return (
        <>
            <h4>Test Slack notifications</h4>

            <button disabled={requestState === REQUEST_STATE_LOADING} className="button button-secondary" onClick={onTestButtonClick}>
                <span className="ppp-slack-icon-paper-plane"></span>
                {buttonLabel}
            </button>

            {requestState === REQUEST_STATE_SUCCESS &&
                <div className="message-box success">
                    <div>{resultMessage}</div>
                </div>
            }

            {requestState === REQUEST_STATE_ERROR &&
                <div className="message-box error">
                    <div>{resultMessage}</div>
                </div>
            }
        </>
    )
}
