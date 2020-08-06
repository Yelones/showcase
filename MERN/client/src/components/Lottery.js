import React, { useState, useEffect } from 'react';
import axios from 'axios';
import moment from 'moment';
import config from '../config';

function Lottery() {
    const [lotteries, setLotteries] = useState([]);

    useEffect(() => {
        axios.get(config.api_url + '/lottery')
            .then(res => {
                setLotteries(res.data);
            })
            .catch(err => console.log(err));
    }, [])

    const generateNumbers = () => {
        axios.post(config.api_url + '/lottery/add')
            .then(res => {
                setLotteries([res.data, ...lotteries]);
            })
            .catch(err => console.log(err));
    }

    return (
        <div>
            <div>
                <button type="button" onClick={generateNumbers}>Generate</button>
            </div>
            <div>
                <table className="lottery-table">
                    <thead>
                        <tr>
                            <th>Week</th>
                            <th colSpan="5">Numbers</th>
                        </tr>
                    </thead>
                    <tbody>
                        {lotteries.length > 0 ? (
                            <React.Fragment>
                                {lotteries.map(lottery => (
                                    <tr key={lottery._id}>
                                        <th>{ moment(lottery.date).format('w') }</th>
                                        {lottery.numbers.map((number, key) => (
                                            <td key={key}>{ number }</td>
                                        ))}
                                    </tr>
                            ))}
                            </React.Fragment>
                        ) : (
                            <tr>
                                <td colSpan="99">No numbers generated yet</td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>
        </div>
    );
}

export default Lottery;
