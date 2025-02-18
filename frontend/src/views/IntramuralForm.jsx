import React, { useEffect, useRef, useState } from 'react'
import axiosClient from '../axiosClient';

export default function 
() {
    const [loading, setLoading] = useState(false);
    const [intramurals, setIntramurals] = useState([]);
    const [errorMessage, setErrorMessage] = useState("");
    const [active, setActive] = useState(true);

    const nameRef = useRef();
    const yearRef = useRef();

    useEffect(() => {
        getIntramurals();
    }, [])


    const getIntramurals = async () => {
        setLoading(true);
        axiosClient.get('/intramurals').then(({ data }) => {
          setLoading(false)
          setIntramurals(data.data)
          console.log(data.data)
        }).catch(() => {
          setLoading(false)
        });
    }

    const handleSubmit = async (ev) => {
        ev.preventDefault();
        const payload = {
        name: nameRef.current.value,
        year: yearRef.current.value,
    };
    
        axiosClient.post("/intramurals", payload).then(({data})=>{
            setErrorMessage("");
        }).catch(err => {
            const response = err.response;
            if(response && response.status === 422){
                console.log("hey");
                console.log(response.data.errors);
            }
        });
    }; 
  return (
    <div>
            <form onSubmit={handleSubmit}>
                <input 
                    ref={nameRef}
                    type="text" 
                    placeholder="Game Name" 
                    name = "game_name" 
                    required 
                />
                <input 
                    ref = {yearRef}
                    type="number" 
                    placeholder="Year"
                    required 
                />
                <button type="submit">Add Game</button>
            </form>
        </div>
  )
}
