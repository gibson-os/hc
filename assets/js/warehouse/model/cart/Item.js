Ext.define('GibsonOS.module.hc.warehouse.model.cart.Item', {
    extend: 'GibsonOS.data.Model',
    fields: [{
        name: 'id',
        type: 'int'
    },{
        name: 'stock',
        type: 'integer'
    },{
        name: 'itemId',
        type: 'integer',
        useNull: true,
    }]
});