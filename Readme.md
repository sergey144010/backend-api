# Backend-api

Simple backend api for develop frontend application

## How run

Create directory for you data, for example
```bash
mkdir ./data
cd data
```

Easy usage docker for see example
```bash
sudo docker run --name=simple-backend-api --rm -p 8765:80 itlab77/backend-api
```
and example route
http://localhost:8765/v1/questions/1

For your data need usage
```bash
sudo docker run -d --name=simple-backend-api --rm -v "$PWD":/data -p 8765:80 itlab77/backend-api
```
and create files like see below

## How add route and data

Create files in your ./data directory, like this
```bash
touch customers.get.200.json
```
And copy to customers.get.200.json file valid json data, like
```bash
echo '{"name":"mkyong.com","messages":["msg 1","msg 2","msg 3"],"age":100}' >> customers.get.200.json
```

That's all. Your data access on route http://localhost:8765/customers

## Usage

Examples map files to route:

- customers.get.200.json - GET /customers
- customers.post.200.json - POST /customers
- customers.put.200.json - PUT /customers
- customers.patch.200.json - PATCH /customers
- customers.delete.200.json - DELETE /customers

where 200 - response status code

- v1.customers.1.get.200.json - GET /v1/customers/1
 
and etc.

## Advanced usage

For advanced usage need use advanced image like this
```bash
sudo docker run -d --name=simple-backend-api --rm -v "$PWD":/data -p 8765:80 itlab77/backend-api-advanced
```

Create file customers.get.200.json with data

```json
{
  "requests":[
    {
      "params":[
        {
          "name": "email", 
          "value": "123@123.com"
        }, 
        {
          "name":"password", 
          "value":"123"
        }
      ], 
      "response": {
        "statusCode": 200, 
        "data": {"name":"mkyong.com","messages":["msg 1","msg 2","msg 3"],"age":100}
      }
    }, 
    {
      "params": [
        {
          "name": "email", 
          "value": "111@123.com"
        }
      ], 
      "response":{
        "statusCode": 404, 
        "data": {"status": "Not found"}
      }
    }
  ], 
  "defaultResponse": {"status": "Fail"}
}
```

Route
http://localhost:8765/customers?email=123@123.com&password=123
take data
```json
{"name":"mkyong.com","messages":["msg 1","msg 2","msg 3"],"age":100}
```
with status code 200

Route
http://localhost:8765/customers?email=111@123.com
take data
```json
{"status": "Not found"}
```
with status code 404

Field 'defaultResponse' not required.