Ext.define('GibsonOS.module.hc.io.store.DirectConnect', {
    extend: 'GibsonOS.data.Store',
    alias: ['hcIoGridStore'],
    groupField: 'inputPortName',
    model: 'GibsonOS.module.hc.io.model.DirectConnect',
    constructor: function(data) {
        var me = this;

        me.proxy = {
            type: 'gosDataProxyAjax',
            url: baseDir + 'hc/io/directConnects',
            extraParams: {
                moduleId: data.gos.data.module.id
            }
        };

        me.callParent(arguments);

        return me;
    }
});