Ext.define('GibsonOS.module.hc.warehouse.label.element.Form', {
    extend: 'GibsonOS.module.core.component.form.Panel',
    alias: ['widget.gosModuleHcWarehouseLabelElementForm'],
    requiredPermission: {
        action: 'save',
        permission: GibsonOS.Permission.WRITE
    },
    initComponent() {
        const me = this;

        me.items = [{
        }];

        me.callParent();
    }
});