Ext.define('GibsonOS.module.hc.master.App', {
    extend: 'GibsonOS.App',
    alias: ['widget.gosModuleHcMasterApp'],
    title: 'Master',
    appIcon: 'icon_homecontrol',
    width: 700,
    height: 400,
    layout: 'border',
    requiredPermission: {
        module: 'hc',
        task: 'master'
    },
    initComponent: function(arguments) {
        let me = this;

        me.title += ': ' + me.master.name;
        me.items = [{
            xtype: 'gosCoreComponentTabPanel',
            enableToolbar: false,
            region: 'center',
            items: [{
                xtype: 'gosModuleHcIndexModuleGrid',
                title: 'Module',
                gos: {
                    data: {
                        extraParams: {
                            masterId: me.master.id
                        }
                    }
                }
            },{
                xtype: 'gosModuleHcIndexLogGrid',
                title: 'Log',
                gos: {
                    data: {
                        extraParams: {
                            masterId: me.master.id
                        }
                    }
                }
            }]
        }];

        me.callParent(arguments);

        if (me.master.offline) {
            GibsonOS.Ajax.request({
                url: baseDir + 'hc/index/lastLog',
                method: 'GET',
                params: {
                    masterId: me.master.id,
                    direction: 'INPUT'
                },
                success(response) {
                    const lastLog = Ext.decode(response.responseText).data;

                    if (lastLog.type === 127) {
                        me.insert(0, {
                            xtype: 'gosCoreComponentNoticePanel',
                            region: 'north',
                            flex: 0,
                            height: 25,
                            text: 'Bus funktioniert nicht. Master muss neugestartet werden!'
                        });
                    }
                }
            });
        }

        me.down('gosModuleHcIndexLogGrid').getStore().on('load', (stroe, records, successful) => {
            if (!successful) {
                return false;
            }

            if (records.length === 0) {
                return false;
            }

            // const lastLog
        });
    }
});