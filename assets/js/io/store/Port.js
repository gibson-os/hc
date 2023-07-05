Ext.define('GibsonOS.module.hc.io.store.Port', {
    extend: 'GibsonOS.data.Store',
    alias: ['hcIoGridStore'],
    autoLoad: true,
    model: 'GibsonOS.module.hc.io.model.Port',
    constructor: function(data) {
        var me = this;

        me.proxy = {
            type: 'gosDataProxyAjax',
            url: baseDir + 'hc/io/ports',
            method: 'GET',
            extraParams: {
                moduleId: data.gos.data.module.id
            }
        };

        me.callParent(arguments);
        me.gos.autoReloadDelay = 500;
        me.gos.autoReload = true;

        return me;
    }
});