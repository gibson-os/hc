Ext.define('GibsonOS.module.hc.index.master.Grid', {
    extend: 'GibsonOS.grid.Panel',
    alias: ['widget.gosModuleHcIndexMasterGrid'],
    itemId: 'hcIndexMasterGrid',
    viewConfig: {
        getRowClass: function (record) {
            if (record.get('offline')) {
                return 'hcModuleOffline';
            }
        },
        listeners: {
            render: function (view) {
                let grid = view.up('gridpanel');

                grid.dragZone = Ext.create('Ext.dd.DragZone', view.getEl(), {
                    getDragData: function (event) {
                        let sourceElement = event.getTarget().parentNode.parentNode;
                        let record = view.getRecord(sourceElement);

                        if (sourceElement) {
                            let clone = sourceElement.cloneNode(true);
                            let data = {
                                module: 'hc',
                                task: 'master',
                                action: 'view',
                                text: record.get('name'),
                                icon: 'icon_homecontrol',
                                params: record.getData()
                            };

                            return grid.dragData = {
                                sourceEl: sourceElement,
                                repairXY: Ext.fly(sourceElement).getXY(),
                                ddel: clone,
                                shortcut: data
                            };
                        }
                    },
                    getRepairXY: function () {
                        return this.dragData.repairXY;
                    }
                });
            }
        }
    },
    initComponent: function (arguments) {
        let me = this;

        me.store = new GibsonOS.module.hc.index.store.Master();
        me.columns = [{
            header: 'Name',
            dataIndex: 'name',
            flex: 1
        },{
            header: 'Protokoll',
            dataIndex: 'protocol',
            width: 100
        }, {
            header: 'Adresse',
            dataIndex: 'address',
            width: 100
        }, {
            header: 'Hinzugefügt',
            dataIndex: 'added',
            width: 130,
            align: 'right'
        }, {
            header: 'Letztes Update',
            dataIndex: 'modified',
            width: 130,
            align: 'right'
        }];
        me.dockedItems = [{
            xtype: 'gosToolbarPaging',
            itemId: 'hcIndexModulePaging',
            store: this.store,
            displayMsg: 'Module {0} - {1} von {2}',
            emptyMsg: 'Keine Master vorhanden'
        }];

        me.callParent(arguments);

        me.on('itemdblclick', function(grid, record) {
            new GibsonOS.module.hc.master.App({gos: {data: {master: record.getData()}}});
        });
    }
});