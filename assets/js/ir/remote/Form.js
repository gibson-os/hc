Ext.define('GibsonOS.module.hc.ir.remote.Form', {
    extend: 'GibsonOS.module.core.component.form.Panel',
    alias: ['widget.gosModuleHcIrRemoteForm'],
    requiredPermission: {
        action: 'saveRemote',
        permission: GibsonOS.Permission.MANAGE + GibsonOS.Permission.WRITE
    },
    initComponent() {
        const me = this;

        me.callParent();
    }
});