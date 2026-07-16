/*
 *  Copyright 2026.  Baks.dev <admin@baks.dev>
 *
 *  Permission is hereby granted, free of charge, to any person obtaining a copy
 *  of this software and associated documentation files (the "Software"), to deal
 *  in the Software without restriction, including without limitation the rights
 *  to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 *  copies of the Software, and to permit persons to whom the Software is furnished
 *  to do so, subject to the following conditions:
 *
 *  The above copyright notice and this permission notice shall be included in all
 *  copies or substantial portions of the Software.
 *
 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *  FITNESS FOR A PARTICULAR PURPOSE AND NON INFRINGEMENT. IN NO EVENT SHALL THE
 *  AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 *  LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 *  OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 *  THE SOFTWARE.
 */

/** Обработчики кнопок выбора товаров */
let select_all = document.querySelector("#select-all");

/** Выбор из списка ответов */
select_all?.addEventListener("click", function(btn)
{
    // Выбрать все
    select_all.classList.toggle("selected");

    if(select_all.classList.contains("selected"))
    {
        select_all.innerText = "Снять выбор";
        select_all.classList.remove("btn-outline-primary");
        select_all.classList.add("btn-primary");
    }
    else
    {
        select_all.innerText = "Выбрать все";
        select_all.classList.add("btn-outline-primary");
        select_all.classList.remove("btn-primary");
    }


    const items = document.querySelectorAll("input[data-formname=\"" + this.dataset.name + "\"]");

    // Выбрать все НЕ disabled (т.е. те, которые не на производстве)
    items.forEach(checkbox =>
    {
        if(!checkbox.disabled)
        {
            checkbox.checked = select_all.classList.contains("selected");
        }
    });

    const btn_sale = document.querySelector("#btn_sale_" + this.dataset.name);

    const checkboxes = document.querySelectorAll("input[data-formname=\"" + this.dataset.name + "\"]");
    const atLeastOneChecked = Array.from(checkboxes).some(cb => cb.checked);

    if(atLeastOneChecked)
    {
        btn_sale.classList.remove("d-none");
    }
    else
    {
        btn_sale.classList.add("d-none");
    }

});


var checkboxs_all = document.querySelectorAll("input[data-formname=\"" + select_all.dataset.name + "\"]");

///** Скрыть или показать кнопку при выборе элемента */
for(checkbox of checkboxs_all)
{
    checkbox?.addEventListener("click", function()
    {
        const checkboxes = document.querySelectorAll("input[data-formname=\"" + select_all.dataset.name + "\"]");
        const atLeastOneChecked = Array.from(checkboxes).some(cb => cb.checked);

        const btn_sale = document.querySelector("#btn_sale_" + select_all.dataset.name);


        if(atLeastOneChecked)
        {
            btn_sale.classList.remove("d-none");
        }
        else
        {
            btn_sale.classList.add("d-none");
        }

    });
}