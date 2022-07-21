Ext.define('GibsonOS.module.hc.warehouse.cart.Panel', {
    extend: 'GibsonOS.module.core.component.Panel',
    alias: ['widget.gosModuleHcWarehouseCartPanel'],
    layout: 'border',
    initComponent() {
        const me = this;

        me.viewItem = new GibsonOS.module.hc.warehouse.box.View({
            region: 'center',
            moduleId: me.moduleId,
            overflowX: 'auto',
            overflowY: 'auto'
        });

        me.items = [{
            xtype: 'gosModuleHcWarehouseCartForm',
            region: 'north',
            flex: 0,
            autoHeight: true
        },{
            xtype: 'gosModuleHcWarehouseCartItemGrid',
            region: 'center'
        }];

        me.callParent();

        me.addAction({
            iconCls: 'icon_system system_save',
            handler() {
            }
        });
    }
});