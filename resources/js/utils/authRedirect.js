/**
 * When the API returns 401 with redirect_url (session missing), perform a full-page navigation.
 * fetch() does not change the document location on HTTP redirects the way a normal navigation does.
 *
 * @param {Response} response
 * @param {object} data Parsed JSON body (may be empty)
 * @returns {boolean} true if navigation was triggered
 */
export function loginGatewayRedirectIfNeeded(response, data) {
    if (Number(response?.status) !== 401 || !data || typeof data.redirect_url !== 'string') {
        return false;
    }
    const url = data.redirect_url.trim();
    if (!url.startsWith('https://') && !url.startsWith('http://')) {
        return false;
    }
    window.location.assign(url);
    return true;
}
