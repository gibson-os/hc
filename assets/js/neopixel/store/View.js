Ext.define('GibsonOS.module.hc.neopixel.store.View', {
    extend: 'GibsonOS.data.Store',
    alias: ['hcNeopixelViewStore'],
    autoLoad: true,
    model: 'GibsonOS.module.hc.neopixel.model.Led',
    constructor: function(data) {
        let me = this;

        me.proxy = {
            type: 'gosDataProxyAjax',
            url: baseDir + 'hc/neopixel/index',
            extraParams: {
                moduleId: data.gos.data.module.id
            }
        };

        me.callParent(arguments);
        me.gos.autoReloadDelay = 500;
        //me.gos.autoReload = true;

        return me;
    }
});