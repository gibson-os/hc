Ext.define('GibsonOS.module.hc.warehouse.cart.Panel', {
    extend: 'GibsonOS.module.core.component.Panel',
    alias: ['widget.gosModuleHcWarehouseCartPanel'],
    layout: 'border',
    initComponent() {
        const me = this;

        me.items = [{
            xtype: 'gosModuleHcWarehouseCartForm',
            region: 'north',
            flex: 0,
            autoHeight: true
        },{
            xtype: 'gosModuleHcWarehouseCartItemGrid',
            region: 'center',
            cartId: me.cartId
        }];

        me.callParent();

        me.addAction({
            iconCls: 'icon_system system_save',
            handler() {
                me.setLoading(true);

                let items = [];
                me.down('grid').getStore().each((item) => {
                    items.push({
                        id: item.get('id'),
                        stock: item.get('stock'),
                        item: {
                            id: item.get('itemId')
                        }
                    });
                });

                GibsonOS.Ajax.request({
                    url: baseDir + 'hc/warehouseCart/save',
                    params:  {
                        id: me.cartId ?? 0,
                        name: me.down('form').getForm().findField('name').getValue(),
                        description: me.down('form').getForm().findField('description').getValue(),
                        items: Ext.encode(items)
                    },
                    callback() {
                        me.setLoading(false);
                    }
                });
            }
        });
    }
});