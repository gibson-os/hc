Ext.define('GibsonOS.module.hc.warehouse.box.Form', {
    extend: 'GibsonOS.module.core.component.form.Panel',
    alias: ['widget.gosModuleHcWarehouseBoxForm'],
    requiredPermission: {
        action: 'save',
        permission: GibsonOS.Permission.MANAGE + GibsonOS.Permission.WRITE
    },
    initComponent() {
        const me = this;

        me.items = [{
            xtype: 'gosCoreComponentFormFieldTextField',
            fieldLabel: 'Name',
            name: 'name'
        }, {
            xtype: 'gosCoreComponentFormFieldNumberField',
            fieldLabel: 'Anzahl',
            name: 'stock',
            minValue: 0
        },{
            xtype: 'gosCoreComponentFormFieldTextArea',
            fieldLabel: 'Beschreibung',
            name: 'description'
        },{
            xtype: 'gosCoreComponentFormFieldNumberField',
            fieldLabel: 'Breite',
            name: 'width',
            minValue: 1,
            maxValue: 25
        }, {
            xtype: 'gosCoreComponentFormFieldNumberField',
            fieldLabel: 'Höhe',
            name: 'height',
            minValue: 1,
            maxValue: 25
        },{
            xtype: 'gosModuleHcWarehouseBoxTabPanel',
            moduleId: me.moduleId
        }];

        me.callParent();
    }
});