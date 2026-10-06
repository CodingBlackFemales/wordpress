export async function publishpressProCallAjaxPostAction(action, args, ajaxUrl, body) {
    let dataUrl = ajaxUrl + '?action=' + action;

    for (const argumentName in args) {
        if (!args.hasOwnProperty(argumentName)) {
            continue;
        }

        dataUrl += '&' + argumentName + '=' + args[argumentName];
    }

    const response = await fetch(dataUrl, {
        method: 'post',
        body: body
    });
    return await response.json();
}
