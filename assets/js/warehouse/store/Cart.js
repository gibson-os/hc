Ext.define('GibsonOS.module.hc.warehouse.store.Cart', {
    extend: 'GibsonOS.data.Store',
    alias: ['store.gosModuleHcWarehouseCartStore'],
    model: 'GibsonOS.module.hc.warehouse.model.Cart',
    autoLoad: true,
    constructor: function(data) {
        let me = this;

        me.proxy = {
            type: 'gosDataProxyAjax',
            url: baseDir + 'hc/warehouseCart',
            method: 'GET'
        };

        me.callParent(arguments);

        return me;
    }
});