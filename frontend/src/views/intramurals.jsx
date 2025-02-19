import axios from 'axios'
import React, { useState,useEffect } from 'react';
import { Link, Outlet } from 'react-router-dom';
import axiosClient from '../axiosClient';


export default function Intramurals() {

    const [loading, setLoading] = useState(false);
    const [intramurals, setIntramurals] = useState([]);

    useEffect(() => {
        getIntramurals();
    }, [])


    const getIntramurals = () => {
        setLoading(true);
        axiosClient.get('/intramurals')
          .then(({ data }) => {
            setLoading(false)
            setIntramurals(data)
          })
          .catch((error) => {
            const response = error.response
            console.log(response);
            setLoading(false)
          })
      }
    
  return (
    <div>
        <div style={{display: 'flex', justifyContent: "space-between", alignItems: "center"}}>
          <h1>Intramurals</h1>
          <Link className="btn-add" to="/intramurals/add">Add new</Link>
        </div>
        <div className="card animated fadeInDown">
          <table>
            <thead>
            <tr>   
              <th>ID</th>
              <th>Name</th>
              <th>Year</th>
            </tr>
            </thead>
            {loading &&
              <tbody>
              <tr>
                <td colSpan="5" className="text-center">
                  Loading...
                </td>
              </tr>
              </tbody>
            }
            {!loading &&
              <tbody>
              {intramurals.map(i => (
                <tr key={i.id}>
                  <td>{i.id}</td>
                  <td>{i.name}</td>
                  <td>{i.year}</td>
                  <td>
                    <Link className="btn-edit" to={'/intramurals/' + i.id}>Edit</Link>
                    &nbsp;
                    <button className="btn-delete" onClick={ev => onDeleteClick(i)}>Delete</button>
                  </td>
                </tr>
              ))}
              </tbody>
            }
          </table>
        </div>
        <div>
            <Outlet/>        
        </div>
    </div>
    
  )
}
