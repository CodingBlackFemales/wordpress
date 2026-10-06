import {publishpressProCallAjaxPostAction} from "./Functions";
import SlackTest from "./slack-test";

const $ = jQuery;

export default function SlackOAuth(props) {

    const REQUEST_STATE_IDLE = 'idle';
    const REQUEST_STATE_WAITING = 'waiting';
    const REQUEST_STATE_SUCCESS = 'success';
    const REQUEST_STATE_ERROR = 'error';
    const INTERVAL_FOR_CALLING_MIDDLEWARE_IN_SECONDS = 5;
    const ONE_SECOND_IN_MILESECONDS = 1000;

    const [requestState, setRequestState] = React.useState(REQUEST_STATE_IDLE);
    const [resultMessage, setResultMessage] = React.useState('');
    const [tokenIsStored, setTokenIsStored] = React.useState(props.tokenIsStored);
    const [collectCode, setCollectCode] = React.useState('');
    const [legacyWebhookUrlIsStored, setLegacyWebhookUrlIsStored] = React.useState(props.legacyWebhookUrlIsStored);

    const onClickConnectButton = function (event) {
        event.preventDefault();

        window.open(props.middlewareUrl);

        setResultMessage('');
        setRequestState(REQUEST_STATE_WAITING);
    }

    const onClickCancelButton = function (event) {
        event.preventDefault();

        setRequestState(REQUEST_STATE_IDLE);
        setResultMessage('');
    }

    const onClickCollectButton = function (event) {
        event.preventDefault();

        let body = new FormData;

        body.append('_ajax_nonce', props.nonce);
        body.append('collect_code', collectCode);

        publishpressProCallAjaxPostAction(
            'publishpress_pro_collect_slack_oauth_token',
            [],
            props.ajaxUrl,
            body
        ).then((result) => {
            if (result.success) {
                setRequestState(REQUEST_STATE_SUCCESS);
                setResultMessage(props.text.msgConnectedSuccessfuly);
                setTokenIsStored(true);
            } else {
                setRequestState(REQUEST_STATE_ERROR);
                setResultMessage(result.data.error);
            }
        });
    }

    const onClickResetButton = function (event) {
        event.preventDefault();

        if (! confirm(props.text.confirmDeleteMsg)) {
            return;
        }

        let body = new FormData;

        body.append('_ajax_nonce', props.nonce);

        publishpressProCallAjaxPostAction(
            'publishpress_pro_delete_slack_oauth_token',
            [],
            props.ajaxUrl,
            body
        ).then((result) => {
            if (result.success) {
                setRequestState(REQUEST_STATE_SUCCESS);
                setResultMessage(props.text.msgTokenDeletedSuccessfuly);
                setTokenIsStored(false);
                setCollectCode('');
            } else {
                setRequestState(REQUEST_STATE_ERROR);
                setResultMessage(result.data.error);
            }
        });
    }

    const onChangeAuthCodeField = function (event) {
        const field = event.target;

        setCollectCode($(field).val().trim().replace(/[^a-zA-Z0-9]/, ''));
    }

    const startButtonLabel = tokenIsStored ? props.text.reconnectButtonLabel : props.text.connectButtonLabel;

    return (
        <>
            {legacyWebhookUrlIsStored && ! tokenIsStored &&
                <div className="message-box warning">
                    <span className="ppp-slack-icon-exclamation-triangle"></span>
                    {props.text.newAPIWarning}
                </div>
            }

            {tokenIsStored &&
                <div className="message-box info">
                    <span className="ppp-slack-icon-happy-outline"></span>
                    {props.text.msgTokenIsStored}
                </div>
            }

            {[REQUEST_STATE_IDLE, REQUEST_STATE_SUCCESS, REQUEST_STATE_ERROR].includes(requestState) &&
                <>
                    <button disabled={requestState === REQUEST_STATE_WAITING} className="button button-secondary" onClick={onClickConnectButton}>
                        <span className="ppp-slack-icon-slack"></span>
                        {startButtonLabel}
                    </button>
                    <button className="button button-secondary" onClick={onClickResetButton}>
                        <span className="ppp-slack-icon-trash"></span>
                        {props.text.resetButtonLabel}
                    </button>
                </>
            }

            {requestState === REQUEST_STATE_WAITING &&
                <>
                    <div className="message-box info">{props.text.msgWaiting}</div>
                    <div className="fields-box">
                        <input type="text" name="authentication_code" id="authentication_code" onChange={onChangeAuthCodeField} />

                        <button className="button button-secondary" disabled={collectCode === ''} onClick={onClickCollectButton}>
                            <span className="ppp-slack-icon-check-circle"></span>
                            {props.text.collectButtonLabel}
                        </button>
                        <button className="button button-secondary" onClick={onClickCancelButton}>
                            <span className="ppp-slack-icon-ban"></span>
                            {props.text.cancelButtonLabel}
                        </button>
                    </div>
                </>
            }

            {[REQUEST_STATE_IDLE, REQUEST_STATE_SUCCESS, REQUEST_STATE_ERROR].includes(requestState) &&
                <div className="message-box info">{props.text.msgIdleState}</div>
            }

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

            {tokenIsStored &&
                <>
                    <hr></hr>
                    <SlackTest
                        nonce={props.nonce_test}
                        ajaxUrl={props.ajaxUrl}
                        text={props.text.test} />
                </>
            }
        </>
    )
}

jQuery(() => {
    ReactDOM.render(
        <SlackOAuth
            text={publishpressProSlackOAuthParams.text}
            nonce={publishpressProSlackOAuthParams.nonce}
            nonce_test={publishpressProSlackOAuthParams.nonce_test}
            clientNonce={publishpressProSlackOAuthParams.clientNonce}
            ajaxUrl={publishpressProSlackOAuthParams.ajaxUrl}
            tokenIsStored={publishpressProSlackOAuthParams.tokenIsStored}
            legacyWebhookUrlIsStored={publishpressProSlackOAuthParams.legacyWebhookUrlIsStored}
            middlewareUrl={publishpressProSlackOAuthParams.middlewareUrl}/>,
        document.getElementById('publishpress_prosettings_slack_oauth_container')
    );
});
