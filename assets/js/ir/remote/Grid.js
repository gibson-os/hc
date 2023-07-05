Ext.define('GibsonOS.module.hc.ir.remote.Grid', {
    extend: 'GibsonOS.module.core.component.grid.Panel',
    alias: ['widget.gosModuleIrRemoteGrid'],
    autoScroll: true,
    multiSelect: true,
    addFunction() {
        const me = this;

        new GibsonOS.module.hc.ir.remote.Window({
            moduleId: me.moduleId
        });
    },
    enterButton: {
        iconCls: 'icon_system system_show',
    },
    enterFunction(remote) {
        new GibsonOS.module.hc.ir.remote.App({
            moduleId: this.moduleId,
            remoteId: remote.get('id')
        });
    },
    deleteFunction(remotes) {
        const me = this;
        let message = 'Möchten Sie die ' + remotes.length + ' Fernbedienungen wirklich löschen?';

        if (remotes.length === 1) {
            message = 'Möchten Sie die Fernbedienung ' + remotes[0].get('name') + ' wirklich löschen?';
        }

        GibsonOS.MessageBox.show({
            title: 'Fernbedienung löschen?',
            msg: message,
            type: GibsonOS.MessageBox.type.QUESTION,
            buttons: [{
                text: 'Ja',
                handler: function() {
                    me.setLoading(true);
                    let ids = [];

                    Ext.iterate(remotes, (remote) => {
                        ids.push({id: remote.get('id')});
                    });

                    GibsonOS.Ajax.request({
                        url: baseDir + 'hc/ir/remotes',
                        method: 'DELETE',
                        params: {
                            remotes: Ext.encode(ids)
                        },
                        success: function() {
                            me.getStore().remove(remotes);
                        },
                        callback() {
                            me.setLoading(false);
                        }
                    });
                }
            },{
                text: 'Nein'
            }]
        });
    },
    initComponent() {
        const me = this;

        me.store = new GibsonOS.module.hc.ir.store.Remote({
            moduleId: me.moduleId,
        });

        me.callParent();

        me.addAction({
            selectionNeeded: true,
            maxSelectionAllowed: 1,
            iconCls: 'icon_system system_edit',
            handler() {
                new GibsonOS.module.hc.ir.remote.Window({
                    moduleId: me.moduleId,
                    remoteId: me.getSelectionModel().getSelection()[0].get('id')
                });
            }
        });
    },
    getColumns() {
        return [{
            header: 'Name',
            dataIndex: 'name',
            flex: 1
        }];
    }
});