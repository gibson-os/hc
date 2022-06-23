Ext.define('GibsonOS.module.hc.warehouse.box.Form', {
    extend: 'GibsonOS.module.core.component.form.Panel',
    alias: ['widget.gosModuleHcWarehouseBoxForm'],
    requiredPermission: {
        action: 'save',
        permission: GibsonOS.Permission.MANAGE + GibsonOS.Permission.WRITE
    },
    initComponent() {
        const me = this;

        me.callParent();
    }
});