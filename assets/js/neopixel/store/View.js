Ext.define('GibsonOS.module.hc.neopixel.store.View', {
    extend: 'GibsonOS.data.Store',
    alias: ['store.gosModuleHcNeopixelViewStore'],
    model: 'GibsonOS.module.hc.neopixel.model.Led',
    autoLoad: false,
    constructor: function(data) {
        let me = this;

        me.proxy = {
            type: 'gosDataProxyAjax',
            url: baseDir + 'hc/neopixel',
            method: 'GET'
        };

        me.callParent(arguments);

        return me;
    }
});