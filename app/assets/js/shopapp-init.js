(function () {
    'use strict';
    if (window.ShopApp && typeof window.shopAppInstance === 'undefined') {
        window.shopAppInstance = new ShopApp(function (app) {
            app.init(null, function (params, app) {
                app.show(null, function () {
                    app.adjustIframeSize();
                });
            }, function (errmsg, app) {
                alert(errmsg);
            });
        }, true);
    }
})();

