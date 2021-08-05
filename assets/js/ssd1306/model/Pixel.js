Ext.define('GibsonOS.module.hc.ssd1306.model.Pixel', {
    extend: 'GibsonOS.data.Model',
    fields: [{
        name: 'page',
        type: 'int'
    },{
        name: 'column',
        type: 'int'
    },{
        name: 'bit',
        type: 'int'
    },{
        name: 'on',
        type: 'boolean'
    }]
});