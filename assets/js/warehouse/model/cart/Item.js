Ext.define('GibsonOS.module.hc.warehouse.model.cart.Item', {
    extend: 'GibsonOS.data.Model',
    fields: [{
        name: 'id',
        type: 'int'
    },{
        name: 'name',
        type: 'string'
    },{
        name: 'item',
        type: 'object'
    }]
});