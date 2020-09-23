Ext.define('GibsonOS.module.hc.neopixel.model.Image', {
    extend: 'GibsonOS.data.Model',
    fields: [{
        name: 'id',
        type: 'int'
    },{
        name: 'name',
        type: 'string'
    },{
        name: 'leds',
        type: 'array'
    }]
});