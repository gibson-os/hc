Ext.define('GibsonOS.module.hc.warehouse.label.Panel', {
    extend: 'GibsonOS.module.core.component.Panel',
    alias: ['widget.gosModuleHcWarehouseLabelPanel'],
    layout: 'border',
    initComponent() {
        const me = this;

        me.items = [{
            xtype: 'gosModuleHcWarehouseLabelGrid',
            region: 'west'
        },{
            region: 'center',
            layout: 'border',
            disabled: true,
            items: [{
                region: 'north',
                cls: 'coloredPanel',
                data: {
                    height: 10,
                    width: 10,
                    elements: []
                },
                tpl: new Ext.XTemplate(
                    '<div ',
                        'class="hcWarehouseLabelPreview" ',
                        'style="height: {height}mm; width: {width}mm;"',
                    '>',
                    '<tpl for="elements">',
                        '<div ',
                            'class="hcWarehouseLabelElement" ',
                            'style="height: {height}mm; width: {width}mm; top: {top}mm; left: {left}mm;"',
                        '></div>',
                    '</tpl>',
                    '</div>'
                )
            },{
                layout: 'border',
                region: 'center',
                items: [{
                    xtype: 'gosModuleHcWarehouseLabelElementGrid',
                    region: 'west'
                },{
                    xtype: 'gosModuleHcWarehouseLabelElementForm',
                    region: 'center'
                }]
            }]
        }];

        me.callParent();
    }
});