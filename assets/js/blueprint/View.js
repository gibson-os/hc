Ext.define('GibsonOS.module.hc.blueprint.View', {
    extend: 'GibsonOS.module.core.component.Panel',
    alias: ['widget.gosModuleHcBlueprintView'],
    requiredPermission: {
        module: 'hc',
        task: 'blueprint'
    },
    cls: 'coloredPanel',
    overflowX: 'auto',
    overflowY: 'auto',
    data: [],
    initComponent() {
        let me = this;

        //me.tpl = new Ext.XTemplate('<img src="' + baseDir + 'hc/blueprint/svg?id=1&childrenTypes[]=FRAME&childrenTypes[]=ROOM&childrenTypes[]=FURNISHING&childrenTypes[]=MODULE" />');
        // me.tpl = new Ext.XTemplate('<object id="svg-object" data="' + baseDir + 'hc/blueprint/svg?id=1&childrenTypes[]=FRAME&childrenTypes[]=ROOM&childrenTypes[]=FURNISHING&childrenTypes[]=MODULE" type="image/svg+xml"></object>');

        me.callParent(arguments);

        me.addAction({
            xtype: 'gosModuleCoreParameterTypeAutoComplete',
            hideLabel: true,
            width: 150,
            enableKeyEvents: true,
            emptyText: 'Grundriss laden',
            addToItemContextMenu: false,
            addToContainerContextMenu: false,
            requiredPermission: {
                action: 'svg',
                method: 'GET',
                permission: GibsonOS.Permission.READ
            },
            parameterObject: {
                config: {
                    model: 'GibsonOS.module.hc.blueprint.model.Blueprint',
                    autoCompleteClassname: 'GibsonOS\\Module\\Hc\\AutoComplete\\BlueprintAutoComplete'
                }
            },
            listeners: {
                select(combo, records) {
                    Ext.Ajax.request({
                        url: baseDir + 'hc/blueprint/svg',
                        method: 'GET',
                        params:  {
                            id: records[0].get('id'),
                            'childrenTypes[]': ['FRAME', 'ROOM', 'FURNISHING', 'MODULE']
                        },
                        success(response) {
                            me.update(response.responseText);
                        }
                    });
                }
            }
        });
    }
});