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

    function adjustCards(){
        document.querySelectorAll('.lg\\:grid-cols-3').forEach(function(el){
            el.classList.remove('lg:grid-cols-3');
            el.classList.add('lg:grid-cols-2');
        });
        document.querySelectorAll('.hanja-wrapper .mt-5.space-y-3').forEach(function(box){
            var ps = box.querySelectorAll('p');
            if(ps[0]) ps[0].style.display = 'none';
            if(ps[1]){
                var m = ps[1].textContent.match(/\d+/);
                if(m) ps[1].textContent = '\uCD1D\uBB38\uD669: ' + m[0];
            }
        });
    }
    document.addEventListener('DOMContentLoaded', function(){
        var tries = 0;
        var iv = setInterval(function(){
            if(document.querySelector('.hanja-wrapper .mt-5.space-y-3') || tries > 20){
                clearInterval(iv); adjustCards();
            }
            tries++;
        }, 500);
    });
})();
