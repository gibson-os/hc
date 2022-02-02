Ext.define('GibsonOS.module.hc.ir.remote.Panel', {
    extend: 'GibsonOS.module.core.component.Panel',
    alias: ['widget.gosModuleHcIrRemotePanel'],
    layout: 'border',
    enableContextMenu: true,
    enableKeyEvents: true,
    addFunction() {
        const store = this.viewItem.getStore();
        let maxTop = 0;

        store.each((key) => {
            if (key.get('left') <= 3) {
                let top = key.get('top');
                let height = key.get('height');
                maxTop = (maxTop > top + height) ? maxTop : (top + height + 1);
            }
        });

        store.add({
            left: 0,
            top: maxTop,
            width: 3,
            height: 3,
            borderTop: true,
            borderRight: true,
            borderBottom: true,
            borderLeft: true,
            borderRadiusTopLeft: 0,
            borderRadiusTopRight: 0,
            borderRadiusBottomLeft: 0,
            borderRadiusBottomRight: 0,
        });
    },
    deleteFunction(records) {
        this.viewItem.getStore().remove(records);
    },
    initComponent() {
        const me = this;

        me.viewItem = new GibsonOS.module.hc.ir.remote.View({
            region: 'center',
            moduleId: me.moduleId,
            overflowX: 'auto',
            overflowY: 'auto',
        });

        me.items = [me.viewItem, {
            xtype: 'gosModuleHcIrRemoteForm',
            region: 'east',
            disabled: true,
            flex: 0,
            width: 300
        }];

        me.callParent();

        me.addActions();
        me.viewItem.on('selectionchange', (view, records) => {
            const form = me.down('form');

            if (records.length !== 1) {
                form.disable();
                form.loadRecord(new GibsonOS.module.hc.ir.model.RemoteKey());

                return;
            }

            form.loadRecord(records[0]);
            form.enable();
        });
    },
    addActions() {
        const me = this;

        me.addAction({xtype: 'tbseparator'});
        me.addAction({
            xtype: 'gosCoreComponentFormFieldTextField',
            itemId: 'name',
            value: me.remote.name,
            emptyText: 'Name',
            hideLabel: true,
            width: 120
        });
        me.addAction({
            iconCls: 'icon_system system_save',
            requiredPermission: {
                action: 'saveRemote',
                permission: GibsonOS.Permission.WRITE + GibsonOS.Permission.MANAGE
            },
            handler: function() {
                let keys = [];

                me.viewItem.store.each(function(key) {
                    keys.push(key.getData());
                });

                GibsonOS.Ajax.request({
                    url: baseDir + 'hc/ir/saveRemote',
                    params: {
                        id: me.moduleId,
                        remoteId: me.remoteId,
                        name: me.down('#name').getValue(),
                        width: me.remote.width,
                        height: me.remote.height,
                        itemWidth: me.remote.itemWidth,
                        keys: Ext.encode(keys)
                    },
                    success: function(response) {
                        me.up('window').close();
                    }
                });
            }
        });
    }
});