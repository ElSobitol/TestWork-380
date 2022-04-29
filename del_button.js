function clearAllFormInputs()
{
    let form = document.getElementById('custom_product_data');
    let inputs = form.getElementsByTagName('_text_field');
    for (let input of inputs)
        input.value = '';
}

let button = document.getElementById('button');
button.addEventListener('click', clearAllFormInputs);

