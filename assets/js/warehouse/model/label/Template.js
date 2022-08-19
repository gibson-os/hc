Ext.define('GibsonOS.module.hc.warehouse.model.label.Template', {
    extend: 'GibsonOS.data.Model',
    fields: [{
        name: 'id',
        type: 'int'
    },{
        name: 'name',
        type: 'string'
    },{
        name: 'pageWidth',
        type: 'float'
    },{
        name: 'pageHeight',
        type: 'float'
    },{
        name: 'rows',
        type: 'int'
    },{
        name: 'columns',
        type: 'int'
    },{
        name: 'marginTop',
        type: 'float'
    },{
        name: 'marginLeft',
        type: 'float'
    },{
        name: 'itemWidth',
        type: 'float'
    },{
        name: 'itemHeight',
        type: 'float'
    },{
        name: 'itemMarginRight',
        type: 'float'
    },{
        name: 'itemMarginBottom',
        type: 'float'
    }]
});