Ext.define('GibsonOS.module.hc.ir.remote.Panel', {
    extend: 'GibsonOS.module.core.component.Panel',
    alias: ['widget.gosModuleHcIrRemotePanel'],
    layout: 'border',
    enableContextMenu: true,
    enableKeyEvents: true,
    remote: {
        name: null,
        itemWidth: 30,
        width: 0,
        height: 0
    },
    initComponent() {
        const me = this;

        me.viewItem = new GibsonOS.module.hc.ir.remote.View({
            region: 'center',
            moduleId: me.moduleId,
            overflowX: 'auto',
            overflowY: 'auto'
        });

        me.items = [me.viewItem, {
            xtype: 'gosModuleHcIrRemoteForm',
            region: 'east',
            flex: 0,
            width: 300
        }];

        me.callParent();

        me.addActions();
    },
    addActions() {
        const me = this;

        me.addAction({
            xtype: 'gosCoreComponentFormFieldTextField',
            itemId: 'name',
            value: me.remote.name,
            emptyText: 'Name',
            hideLabel: true,
            width: 120
        });
        me.addAction({
            xtype: 'gosCoreComponentFormFieldNumberField',
            itemId: 'itemWidth',
            value: me.remote.itemWidth,
            emptyText: 'Breite',
            hideLabel: true,
            width: 70,
            minValue: 15,
            maxValue: 280,
            listeners: {
                change: function(numberfield, newValue) {
                    me.remote.itemWidth = newValue;
                }
            }
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
        me.addAction({xtype: 'tbseparator'});
        me.addAction({
            tbarText: 'Neue Reihe',
            text: 'Neue Reihe',
            requiredPermission: {
                action: 'saveRemote',
                permission: GibsonOS.Permission.WRITE + GibsonOS.Permission.MANAGE
            },
            handler: function() {
                me.remote.height++;

                for (let i = 0; i < me.remote.width; i++) {
                    me.viewItem.store.add({
                        width: 1,
                        height: 1,
                        left: i,
                        top: me.remote.height-1
                    });
                }
            }
        });
        me.addAction({
            tbarText: 'Reihe entfernen',
            text: 'Reihe entfernen',
            requiredPermission: {
                action: 'saveRemote',
                permission: GibsonOS.Permission.WRITE + GibsonOS.Permission.MANAGE
            },
            handler: function() {
                let keys = [];
                me.remote.height--;

                me.viewItem.store.each(function(key) {
                    if (key.get('top') !== me.remote.height) {
                        keys.push(key);
                    }
                });

                me.viewItem.store.loadData(keys);
            }
        });
        me.addAction({xtype: 'tbseparator'});
        me.addAction({
            tbarText: 'Neue Spalte',
            text: 'Neue Spalte',
            requiredPermission: {
                action: 'saveRemote',
                permission: GibsonOS.Permission.WRITE + GibsonOS.Permission.MANAGE
            },
            handler: function() {
                me.remote.width++;

                for (let i = 0; i < me.remote.height; i++) {
                    me.viewItem.store.add({
                        width: 1,
                        height: 1,
                        left: me.remote.width-1,
                        top: i
                    });
                }
            }
        });
        me.addAction({
            tbarText: 'Spalte entfernen',
            text: 'Spalte entfernen',
            requiredPermission: {
                action: 'saveRemote',
                permission: GibsonOS.Permission.WRITE + GibsonOS.Permission.MANAGE
            },
            handler: function() {
                let keys = [];
                me.remote.width--;

                me.viewItem.store.each(function(key) {
                    if (key.get('left') !== me.remote.width) {
                        keys.push(key);
                    }
                });

                me.viewItem.store.loadData(keys);
            }
        });
    }
});