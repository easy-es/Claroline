(function(){
    var twigWorkspaceId = document.getElementById('twig-attributes').getAttribute('data-workspaceId');
    var twigDeleteTranslation = document.getElementById('twig-attributes').getAttribute('data-translation.delete');

    var nbIterationUsers=0;
    $('#user-loading').hide();

    $('.link-delete-user').live('click', function(e){
        var route = Routing.generate('claro_workspace_delete_user', {'userId': $(this).attr('data-user-id'), 'workspaceId': twigWorkspaceId});
        var element = $(this).parent().parent();
        ClaroUtils.sendRequest(
            route,
            function(data){
                element.remove();
            },
            undefined,
            'DELETE'
            )
    })

    $('#bootstrap-modal').modal({
        show: false,
        backdrop: false
    });

    $('#bootstrap-modal').on('hidden', function(){
        /*$('#modal-login').empty();
        $('#modal-body').show();*/
        //the page must be reloaded or it'll break dynatree
        if ($('#modal-login').find('form').attr('id') == 'login_form'){
            window.location.reload();
        }
    })

    $('#add-user-button').click(function(){
        $('#bootstrap-modal-user').modal('show');
    });

    $('#btn-save-users').on('click', function(event){
        var parameters = {};
        var i = 0;
        $('.checkbox-user-name:checked').each(function(index, element){
            parameters[i] = element.value;
            i++;
        })
        parameters.workspaceId = twigWorkspaceId;
        var route = Routing.generate('claro_workspace_multiadd_user', parameters);
        ClaroUtils.sendRequest(
            route,
            function(data){createUserCallback(data)},
            undefined,
            'PUT'
        )
        $('#bootstrap-modal-user').modal('hide');
        $('.checkbox-user-name').remove();
        $('#user-table-checkboxes-body').empty();
        nbIterationUsers = 0;
    });

    $('#lazy-load-user-button').click(function(){
        $('#user-loading').show();
        var route = Routing.generate('claro_workspace_users_paginated', {'workspaceId': twigWorkspaceId, 'page': nbIterationUsers});
        ClaroUtils.sendRequest(
            route,
            function(data){
                if (nbIterationUsers == 0){
                    $('.checkbox-user-name').remove();
                    $('#user-table-checkboxes-body').empty();
                }
                nbIterationUsers++;
                createUsersChkBoxes(data);
                $('#user-loading').hide();
            }
        );
    });

    $('#search-user-button').click(function(){
        var search = document.getElementById('search-user-txt').value;
        if (search !== ''){
            $('#user-loading').show();
            nbIterationUsers = 0;
            var route = Routing.generate('claro_workspace_search_unregistered_users', {'search': search, 'workspaceId': twigWorkspaceId})
            ClaroUtils.sendRequest(
                route,
                function(data){
                    $('.checkbox-user-name').remove();
                    $('#user-table-checkboxes-body').empty();
                    createUsersChkBoxes(data);
                    $('#user-loading').hide();
                }
            );
        }
    });

    function createUsersChkBoxes(JSONString)
    {
        JSONObject = eval(JSONString);
        //chkboxes creation
        var i=0;
        while (i<JSONObject.length)
        {
            var list = '<tr>'
            +'<td align="center"><input class="checkbox-user-name" id="checkbox-user-'+JSONObject[i].id+'" type="checkbox" value="'+JSONObject[i].id+'" id="checkbox-user-'+JSONObject[i].id+'"></input></td>'
            +'<td align="center">'+JSONObject[i].username+'</td>'
            +'<td align="center">'+JSONObject[i].lastName+'</td>'
            +'<td align="center">'+JSONObject[i].firstName+'</td>'
            +'</tr>';
            $('#user-table-checkboxes-body').append(list);
            i++;
        }
    }

    function createUserCallback(JSONString)
    {
        JSONObject = eval(JSONString);
        var i=0;
        while (i<JSONObject.length)
        {
            var row = '<tr class="row-user">'
            +'<td align="center">'+JSONObject[i].username+'</td>'
            +'<td align="center">'+JSONObject[i].lastName+'</td>'
            +'<td align="center">'+JSONObject[i].firstName+'</td>'
            +'<td>'
            +'<a data-user-id="'+JSONObject[i].id+'"href="#" id="link_delete_user_'+JSONObject[i].id+'" class="link-delete-user"> '+twigDeleteTranslation+'</a>'
            +'</td>'
            +'</tr>';
            $('#body-tab-user').append(row);
            i++;
        }
    }
})()
