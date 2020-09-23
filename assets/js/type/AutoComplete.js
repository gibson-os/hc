Ext.define('GibsonOS.module.hc.type.AutoComplete', {
    extend: 'GibsonOS.form.AutoComplete',
    alias: ['widget.gosModuleHcTypeAutoComplete'],
    itemId: 'ftpSessionAutoComplete',
    url: baseDir + 'hc/type/autoComplete',
    model: 'GibsonOS.module.hc.type.model.AutoComplete',
    requiredPermission: {
        module: 'hc',
        task: 'type',
        action: 'autoComplete',
        permission: GibsonOS.Permission.READ
    }
});