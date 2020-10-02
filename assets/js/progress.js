const elMessage = document.getElementById('message')
const elProgress = document.getElementById('progress_bar')
const elBtnProgress = document.getElementById('btn_start_process')
const elPercentageCompleted = document.getElementById('percentage_completed')
const elAmountCompleted = document.getElementById('amount_completed')
const elFailedProducts = document.getElementById('failed_products')
const maxProducts = elProgress.max

let abortController = new AbortController()

function init () {
  console.log('init')
  abortController = new AbortController()
  elBtnProgress.innerHTML = 'Start script'
  elBtnProgress.removeEventListener('click', abort)
  elBtnProgress.addEventListener('click', startProcess)
}

init()

function abort () {
  console.log('abort')
  abortController.abort()
  init()
}

async function startProcess () {
  console.log('startprocess')
  elBtnProgress.innerHTML = 'IN PROGRESS (click to stop)'
  elBtnProgress.addEventListener('click', abort)
  elBtnProgress.removeEventListener('click', startProcess)
  elFailedProducts.innerHTML = ''
  elMessage.innerHTML = ''

  for (let i = 0; i < maxProducts; i++) {
    const options = 'action=' + ap_progress.action + '&offset=' + i + '&max=' + maxProducts
    await checkProduct(options, abortController.signal)
  }

  elProgress.value = 0
  elAmountCompleted.innerHTML = '0'
  elPercentageCompleted.innerHTML = '0'
  elMessage.innerHTML += 'The process is complete <br>'
  init()
}

async function checkProduct (options, abortSignal) {
  const response = await fetch(ajaxurl, {
    method: 'post',
    signal: abortSignal,
    credentials: 'same-origin',
    body: options,
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded; charset=utf-8'
    }
  })
  const data = await response.json()

  elProgress.value += 1
  elAmountCompleted.innerHTML = elProgress.value
  elPercentageCompleted.innerHTML = String(Math.floor((elProgress.value / maxProducts) * 100))

  const productContainer = document.createElement('fieldset')
  productContainer.style.all = 'revert'
  const productIdContainer = document.createElement('legend')
  productIdContainer.innerText = `ProductId: ${data.productId}`
  productContainer.appendChild(productIdContainer)
  const messagesContainer = document.createElement('ul')
  messagesContainer.style.all = 'revert'
  data.messages.forEach((message) => {
    const messageContainer = document.createElement('li')
    messageContainer.innerText = message
    messagesContainer.appendChild(messageContainer)
  })
  productContainer.appendChild(messagesContainer)

  if (data.error === true) {
    elFailedProducts.appendChild(productContainer)
    elFailedProducts.scrollTop = elFailedProducts.scrollHeight
  } else {
    elMessage.appendChild(productContainer)
    elMessage.scrollTop = elMessage.scrollHeight
  }
}