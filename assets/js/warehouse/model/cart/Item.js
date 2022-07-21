Ext.define('GibsonOS.module.hc.warehouse.model.cart.Item', {
    extend: 'GibsonOS.data.Model',
    fields: [{
        name: 'id',
        type: 'int'
    },{
        name: 'name',
        type: 'string',
        convert(value, record) {
            return record.get('item').name;
        }
    },{
        name: 'stock',
        type: 'integer'
    },{
        name: 'item',
        type: 'object'
    }]
});