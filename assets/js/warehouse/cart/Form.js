Ext.define('GibsonOS.module.hc.warehouse.cart.Form', {
    extend: 'GibsonOS.module.core.component.form.Panel',
    alias: ['widget.gosModuleHcWarehouseCartForm'],
    requiredPermission: {
        action: 'save',
        permission: GibsonOS.Permission.WRITE
    },
    initComponent() {
        const me = this;

        me.items = [{
            xtype: 'gosCoreComponentFormFieldTextField',
            name: 'name',
            fieldLabel: 'Name'
        },{
            xtype: 'gosCoreComponentFormFieldTextArea',
            name: 'description',
            fieldLabel: 'Beschreibung'
        }];

        me.callParent();
    }
});