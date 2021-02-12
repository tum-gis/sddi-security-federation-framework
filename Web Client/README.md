# Frontend Implementation

### Web Client Implementation

To employ the SDDI Security Framework, 
the JavaScript library [hello.js](https://github.com/MrSwitch/hello.js) 
can be used as shown in the following source codes:

Custom ``hello.js`` module 
(taken from [HelloSSD.js](https://github.com/tum-gis/qeop-web-map-security/blob/master/3dwebclient/utils/HelloSSD.js)
in our implementation for the web client):
```javascript
(function (hello) {
    hello.init({
        "ssd": {
            name: "SSD",

            oauth: {
                version: 2,
                auth: "https://ssdas.gis.bgu.tum.de/oauth/authorize",
                grant: "https://ssdas.gis.bgu.tum.de/oauth/token"
            },

            scope: {
                basic: 'openid profile',
            },

            login: function (p) {
                p.qs.nonce = "123";
                p.qs.response_type = "id_token token";

                // The login window in a different size
                var w = 610;
                var h = 750;

                // https://stackoverflow.com/questions/4068373/center-a-popup-window-on-screen
                // Fixes dual-screen position                         Most browsers      Firefox
                var dualScreenLeft = window.screenLeft != undefined ? window.screenLeft : screen.left;
                var dualScreenTop = window.screenTop != undefined ? window.screenTop : screen.top;
                var width = window.innerWidth ? window.innerWidth : document.documentElement.clientWidth ? document.documentElement.clientWidth : screen.width;
                var height = window.innerHeight ? window.innerHeight : document.documentElement.clientHeight ? document.documentElement.clientHeight : screen.height;
                var left = ((width / 2) - (w / 2)) + dualScreenLeft;
                var top = ((height / 2) - (h / 2)) + dualScreenTop;

                p.options.popup.width = w;
                p.options.popup.height = h;
                p.options.popup.left = left;
                p.options.popup.top = top;
            },

            logout: function(callback, options) {
                var client_id = "<CLIENT_ID>";
                var token = (options.authResponse || {}).access_token;

                var xhttp_token = new XMLHttpRequest();
                xhttp_token.onreadystatechange = function() {
                    if (this.readyState == 4 && this.status == 200) {
                        hello.utils.store(options, null);
                        // Change the redirect URL after the parameter ``return`` when needed
                        // This is an encoded URL
                        callback("https%3A%2F%2Fssdas.gis.bgu.tum.de%2Foauth%2Flogout%3Freturn%3Dhttps%3A%2F%2Fwww.3dcitydb.org%2Fqeop-web-map-security%2F3dwebclient%2Findex.html%3Ftitle%3D3DCityDB-Web-Map-Client%26shadows%3Dfalse%26terrainShadows%3D0%26latitude%3D51.54598057331442%26longitude%3D-0.012735535769945816%26height%3D138.82756086880357%26heading%3D356.0176491942962%26pitch%3D-53.16764902308586%26roll%3D359.9781813714569%26layer_0%3Durl%253Dhttps%25253A%25252F%25252Fwww.3dcitydb.org%25252F3dcitydb%25252Ffileadmin%25252Fmydata%25252FLondon_QEOP_Demo%25252FQEOP_Buildings_v3%25252FQEOP_Buildings_LoD2_collada_MasterJSON.json%2526name%253DQEOP_Buildings_LoD2%2526active%253Dtrue%2526spreadsheetUrl%253Dhttps%25253A%25252F%25252Fssdwfs.gis.bgu.tum.de%25252Fcitydb-wfs-qeop%25252Fwfs%25253FSERVICE%25253DWFS%252526VERSION%25253D2.0.0%252526REQUEST%25253DGetFeature%2526cityobjectsJsonUrl%253Dhttps%25253A%25252F%25252Fwww.3dcitydb.org%25252F3dcitydb%25252Ffileadmin%25252Fmydata%25252FLondon_QEOP_Demo%25252FQEOP_Buildings_v3%25252FQEOP_Buildings_LoD2.json%2526minLodPixels%253D50%2526maxLodPixels%253D1.7976931348623157e%25252B308%2526maxSizeOfCachedTiles%253D200%2526maxCountOfVisibleTiles%253D200%26layer_1%3Durl%253Dhttps%25253A%25252F%25252Fwww.3dcitydb.org%25252F3dcitydb%25252Ffileadmin%25252Fmydata%25252FLondon_QEOP_Demo%25252FQEOP_Roads_v2%25252FQEOP_Road_Footprints_footprint_MasterJSON.json%2526name%253DQEOP_Streets%2526active%253Dfalse%2526spreadsheetUrl%253Dhttps%25253A%25252F%25252Fwww.google.com%25252Ffusiontables%25252FDataSource%25253Fdocid%25253D1iwLlUEZkgMfSonGYaXV0QuZgGzNex8DzOlbvxTfd%2526cityobjectsJsonUrl%253Dhttps%25253A%25252F%25252Fwww.3dcitydb.org%25252F3dcitydb%25252Ffileadmin%25252Fmydata%25252FLondon_QEOP_Demo%25252FQEOP_Roads_v2%25252FQEOP_Road_Footprints.json%2526minLodPixels%253D50%2526maxLodPixels%253D1.7976931348623157e%25252B308%2526maxSizeOfCachedTiles%253D200%2526maxCountOfVisibleTiles%253D200");
                    }
                };
                xhttp_token.open("POST", "https://ssdas.gis.bgu.tum.de/oauth/tokenrevoke", true);
                xhttp_token.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
                xhttp_token.send("client_id=" + client_id + "&token=" + token);
            },

            // Refresh the access_token once expired
            refresh: true,

            // OAuth2 standard defines SPACE as scope delimiter, hello.js defaults to ','
            scope_delim: " ",

            // Changed according to: https://github.com/MrSwitch/hello.js/issues/167
            xhr: function (p) {
                var token = p.query.access_token;
                delete p.query.access_token;

                if (token) {
                    p.headers = {
                        "Authorization": "Bearer " + token
                    };
                }

                return true;
            }
        }
    });
})(hello);
```

Then implement 
(taken from [SigninManager.js](https://github.com/tum-gis/qeop-web-map-security/blob/master/3dwebclient/utils/SigninManager.js)
in our implementation for the web client):
```javascript
hello.init({
    ssd: "<CLIENT_ID>"
}, {redirect_uri: (window.location + "")});

hello.on('auth.login', function (auth) {
    // Call user information, for the given network
    var session = hello('ssd').getAuthResponse();
    ssdAccessToken = session.access_token;

    if (ssdAccessToken) {
        var loggedin_username = parseJwt(session.id_token).name;
        var helloSSDButton = document.getElementById("hello_ssd_button");
        if (loggedin_username) {
            var preferred_username_initials = shortenName(loggedin_username);
            helloSSDButton.innerHTML = preferred_username_initials;
            helloSSDButton.style.fontSize = "small";
        } else {
            helloSSDButton.innerHTML = "&#x1f513;";
            helloSSDButton.style.fontSize = "medium";
        }
        helloSSDButton.style.color = "yellow";
        helloSSDButton.style.textAlign = "center";
        helloSSDButton.title = "Click to log out";
        CitydbUtil.showAlertWindow('OK', 'Information', 'Welcome' + (loggedin_username ? (", " + loggedin_username) : "") + '!');

        helloSSDButton.onclick = function () {
            doLogout();
        }
    }
});

hello.on('auth.logout', function (auth) {
    var helloSSDButton = document.getElementById("hello_ssd_button");
    helloSSDButton.title = "Click to log in";
    helloSSDButton.innerHTML = "&#x1f511;";
    helloSSDButton.style.fontSize = "medium";
    helloSSDButton.style.color = "yellow";
    helloSSDButton.style.textAlign = "center";

    helloSSDButton.onclick = function () {
        doLogin();
    }
});

function doLogin() {
    hello('ssd').login({
        // Define the scopes (here in login, in case also other login providers shall be used)
        // scope: "…"
    }).then(function (response) {
        // Handle the response
    }, function (e) {
        // In case an error happened when doing the login
        var xhttp = new XMLHttpRequest();
        xhttp.onreadystatechange = function () {
            // if (this.readyState == 4 && this.status == 200) {}
        };
        xhttp.open("GET", "https://ssdds.gis.bgu.tum.de/WAYF/deleteSettings?return=", true);
        xhttp.withCredentials = true;
        xhttp.send();
    });
}

function doLogout() {
    hello('ssd').logout({force: true}, function (url) {
        window.location.replace(url);
    }).then(function () {
        helloSSDButton.onclick = function () {
            doLogin();
        }
    }, function (e) {
        CitydbUtil.showAlertWindow('OK', 'Error', 'Signed out error: ' + e.error.message);
    });
}

// https://stackoverflow.com/questions/38552003/how-to-decode-jwt-token-in-javascript
function parseJwt(id_token) {
    var base64Url = id_token.split('.')[1];
    var base64 = base64Url.replace(/-/g, '+').replace(/_/g, '/');
    return JSON.parse(window.atob(base64));
}

function shortenName(username) {
    if (username) {
        var ss = username.split(/[.,; -]/);
        var result = "";
        for (var i = 0; i < ss.length; i++) {
            result += ss[i].charAt(0).toUpperCase();
        }
        return result;
    }
}

createLoginButton();
function createLoginButton() {
    var customCesiumViewerToolbar = document.getElementsByClassName("cesium-viewer-toolbar")[0];

    if (Cesium.defined(customCesiumViewerToolbar)) {
        // create SSD log in button
        var anchorButton = customCesiumViewerToolbar.getElementsByClassName("cesium-button cesium-toolbar-button cesium-home-button")[0];
        if (!anchorButton) {
            anchorButton = customCesiumViewerToolbar.getElementsByClassName("cesium-button cesium-toolbar-button tracking-deactivated")[0];
        }
        var helloSSDButton = document.createElement("BUTTON");
        helloSSDButton.id = "hello_ssd_button";
        helloSSDButton.innerHTML = "&#x1f511;";
        helloSSDButton.style.fontSize = "medium";
        helloSSDButton.title = "Click to log in to SSD";
        helloSSDButton.style.color = "#edffff";
        helloSSDButton.style.fontWeight = "bold";
        helloSSDButton.className = "cesium-button cesium-toolbar-button";
        helloSSDButton.onclick = function () {
            doLogin();
        }
        customCesiumViewerToolbar.insertBefore(helloSSDButton, anchorButton);

    } else {
        setTimeout(createLoginButton, 100);
    }
}
```