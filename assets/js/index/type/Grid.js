Ext.define('GibsonOS.module.hc.index.type.Grid', {
    extend: 'GibsonOS.grid.Panel',
    alias: ['widget.gosModuleHcIndexTypeGrid'],
    itemId: 'hcIndexTypeGrid',
    initComponent: function () {
        var grid = this;

        this.store = new GibsonOS.module.hc.index.store.Type();
        this.columns = [{
            header: 'ID',
            dataIndex: 'id',
            width: 30,
            align: 'right'
        },{
            header: 'Name',
            dataIndex: 'name',
            flex: 1
        },{
            header: 'Helper',
            dataIndex: 'helper',
            flex: 1
        }];
        this.dockedItems = [{
            xtype: 'gosToolbarPaging',
            itemId: 'hcIndexTypePaging',
            store: this.store,
            displayMsg: 'Typen {0} - {1} von {2}',
            emptyMsg: 'Keine Typen vorhanden'
        }];

        this.callParent();
    }
});