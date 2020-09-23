Ext.define('GibsonOS.module.hc.blank.element.DataFormat', {
    extend: 'GibsonOS.form.ComboBox',
    alias: ['widget.gosModuleHcBlankElementDataFormat'],
    name: 'dataFormat',
    fieldLabel: 'Datenformat',
    allowBlank: false,
    store: {
        xtype: 'gosDataStore',
        fields: ['id', 'name'],
        data: [{
            id: 'bin',
            name: 'Bin'
        },{
            id: 'hex',
            name: 'Hex'
        },{
            id: 'int',
            name: 'Int'
        }]
    }
});