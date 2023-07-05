Ext.define('GibsonOS.module.hc.warehouse.store.label.Template', {
    extend: 'GibsonOS.data.Store',
    alias: ['store.gosModuleHcWarehouseLabelTemplateStore'],
    model: 'GibsonOS.module.hc.warehouse.model.label.Template',
    autoLoad: true,
    constructor(data) {
        let me = this;

        me.proxy = {
            type: 'gosDataProxyAjax',
            url: baseDir + 'hc/warehouseLabel/templates',
            method: 'GET'
        };

        me.callParent(arguments);

        return me;
    }
});