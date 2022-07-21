Ext.define('GibsonOS.module.hc.warehouse.cart.App', {
    extend: 'GibsonOS.App',
    alias: ['widget.gosModuleHcWarehouseCartApp'],
    title: 'Warenkorb',
    appIcon: 'icon_led',
    width: 900,
    height: 850,
    requiredPermission: {
        module: 'hc',
        task: 'warehouseCart'
    },
    initComponent() {
        const me = this;

        me.title = me.title + ': ' + me.gos.data.module.name;
        me.items = [{
            xtype: 'gosModuleHcWarehouseCartPanel',
            cartId: me.cartId
        }];

        me.callParent();
    }
});