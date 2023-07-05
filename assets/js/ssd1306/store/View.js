Ext.define('GibsonOS.module.hc.ssd1306.store.View', {
    extend: 'GibsonOS.data.Store',
    alias: ['store.gosModuleHcSsd1306ViewStore'],
    model: 'GibsonOS.module.hc.ssd1306.model.Pixel',
    constructor(data) {
        const me = this;

        me.proxy = {
            type: 'gosDataProxyAjax',
            url: baseDir + 'hc/ssd1306',
            method: 'GET'
        };

        me.callParent(arguments);

        return me;
    }
});