Ext.define('GibsonOS.module.hc.warehouse.store.label.Element', {
    extend: 'GibsonOS.data.Store',
    alias: ['store.gosModuleHcWarehouseLabelElementStore'],
    model: 'GibsonOS.module.hc.warehouse.model.label.Element',
    autoLoad: false,
    constructor: function(data) {
        let me = this;

        me.proxy = {
            type: 'gosDataProxyAjax',
            url: baseDir + 'hc/warehouseLabel/elements'
        };

        me.callParent(arguments);

        return me;
    }
});