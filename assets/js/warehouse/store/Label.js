Ext.define('GibsonOS.module.hc.warehouse.store.Label', {
    extend: 'GibsonOS.data.Store',
    alias: ['store.gosModuleHcWarehouseLabelStore'],
    model: 'GibsonOS.module.hc.warehouse.model.Label',
    autoLoad: true,
    constructor: function(data) {
        let me = this;

        me.proxy = {
            type: 'gosDataProxyAjax',
            url: baseDir + 'hc/warehouseLabel',
            method: 'GET'
        };

        me.callParent(arguments);

        return me;
    }
});