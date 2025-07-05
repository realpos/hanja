(function(){
    window.hanjaSaveResult = function(result){
        fetch(HanjaAjax.ajax_url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({
                action: 'hanja_save_result',
                result: result
            })
        }).then(res => res.json()).then(data => {
            console.log('Saved', data);
        });
    };
})();
