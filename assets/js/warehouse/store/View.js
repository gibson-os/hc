Ext.define('GibsonOS.module.hc.warehouse.store.View', {
    extend: 'GibsonOS.data.Store',
    alias: ['store.gosModuleHcWarehouseViewStore'],
    model: 'GibsonOS.module.hc.warehouse.model.Box',
    autoLoad: true,
    constructor: function(data) {
        let me = this;

        me.proxy = {
            type: 'gosDataProxyAjax',
            url: baseDir + 'hc/warehouse/index',
            extraParams: {
                moduleId: data.moduleId
            }
        };

        me.callParent(arguments);

        return me;
    }
});