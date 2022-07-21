Ext.define('GibsonOS.module.hc.warehouse.store.cart.Item', {
    extend: 'GibsonOS.data.Store',
    alias: ['store.gosModuleHcWarehouseCart)ItemStore'],
    model: 'GibsonOS.module.hc.warehouse.model.cart.Item',
    autoLoad: true,
    constructor: function(data) {
        let me = this;

        me.proxy = {
            type: 'gosDataProxyAjax',
            url: baseDir + 'hc/warehouseCart/items'
        };

        me.callParent(arguments);

        return me;
    }
});