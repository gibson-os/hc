Ext.define('GibsonOS.module.hc.warehouse.cart.App', {
    extend: 'GibsonOS.App',
    alias: ['widget.gosModuleHcWarehouseCartApp'],
    title: 'Warenkorb',
    appIcon: 'icon_led',
    width: 600,
    height: 500,
    requiredPermission: {
        module: 'hc',
        task: 'warehouseCart'
    },
    initComponent() {
        const me = this;

        me.items = [{
            xtype: 'gosModuleHcWarehouseCartPanel',
            cartId: me.cartId
        }];

        me.callParent();
    }
});