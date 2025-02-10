import { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'
import { RouterProvider } from 'react-router-dom'
import App from './App.jsx'
import './index.css'
import router from './router.jsx'
import { ContextProvider } from './contexts/contextprovider.jsx'

createRoot(document.getElementById('root')).render(
  <StrictMode>
    <ContextProvider>
    <RouterProvider router={router}/>
    </ContextProvider>
  </StrictMode>,
)
