Ext.define('GibsonOS.module.hc.warehouse.model.cart.Item', {
    extend: 'GibsonOS.data.Model',
    fields: [{
        name: 'id',
        type: 'int'
    },{
        name: 'itemId',
        type: 'integer',
        convert(value, record) {
            return record.get('item').id;
        }
    },{
        name: 'available',
        type: 'integer',
        convert(value, record) {
            return record.get('item').stock;
        }
    },{
        name: 'stock',
        type: 'integer'
    },{
        name: 'item',
        type: 'object'
    }]
});