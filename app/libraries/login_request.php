<HTML><HEAD><TITLE>Please log in</TITLE></HEAD>
<BODY bgcolor='#000000' onload='document.forms[0].username.focus()'>
<SCRIPT>//'"]]>>isc_loginRequired

//
// Embed this whole script block VERBATIM into your login page to enable
// SmartClient RPC relogin.

while (!window.isc && document.domain.indexOf(".") != -1) {
    try {
        if (parent.isc == null) {
            document.domain = document.domain.replace(/.*?\./, '');
            continue;
        } 
        break;
    } catch (e) {
        document.domain = document.domain.replace(/.*?\./, '');
    }
}
var isc = top.isc ? top.isc : window.opener ? window.opener.isc : null;
if (isc) isc.RPCManager.delayCall("handleLoginRequired", [window]);
</SCRIPT>
</BODY></HTML>

