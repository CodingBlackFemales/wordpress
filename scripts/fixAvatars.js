const { exec } = require("child_process");
const fs = require("fs");
const path = require("path");
const readline = require("readline");

function importJSON(filePath) {
  return new Promise((resolve, reject) => {
    try {
      const jsonArray = [];
      const fileStream = fs.createReadStream(filePath);
      const rl = readline.createInterface({
        input: fileStream,
        crlfDelay: Infinity,
      });

      rl.on("line", (line) => {
        try {
          const jsonObj = JSON.parse(line);
          if (
            typeof jsonObj === "object" &&
            jsonObj !== null &&
            Object.prototype.hasOwnProperty.call(jsonObj, "ID")
          ) {
            jsonArray.push(jsonObj);
          }
        } catch (error) {
          console.error(`Error parsing JSON from line "${line}":`, error);
        }
      });

      rl.on("close", () => {
        console.log(
          `Imported ${jsonArray.length} JSON objects from ${filePath}`
        );
        resolve(jsonArray);
      });
    } catch (error) {
      console.error("Error importing JSON file:", error);
      reject(error);
    }
  });
}

function getMappedUsers(oldUsers, newUsers) {
  const newUsersMap = new Map(
    newUsers.map((newUser) => [newUser.user_login, newUser.ID])
  );
  const userMap = new Map();
  userMap.has();
  for (const oldUser of oldUsers) {
    const matchingNewUserId = newUsersMap.get(oldUser.user_login);

    if (matchingNewUserId) {
      userMap.set(oldUser.ID, matchingNewUserId);
    }
  }

  return userMap;
}

function renameDirectories(directoryPath, userMap) {
  console.log(`Processing ${directoryPath}`);
  const files = fs.readdirSync(directoryPath, { withFileTypes: true });

  const numericDirectories = files
    .filter((file) => {
      const numericName = parseInt(file.name, 10);
      return (
        file.isDirectory() &&
        /^\d+$/.test(file.name) &&
        userMap.has(numericName) &&
        userMap.get(numericName) !== numericName
      );
    })
    .sort((a, b) => parseInt(a.name, 10) - parseInt(b.name, 10));

  for (const dir of numericDirectories) {
    const oldName = parseInt(dir.name, 10);
    const newName = userMap.get(oldName);
    const oldPath = `${directoryPath}/${oldName}`;
    const newPath = `${directoryPath}/${newName}`;

    fs.renameSync(oldPath, newPath);
    console.log(`Renamed ${oldPath} to ${newPath}`);
  }
}

function runShellCommand(command) {
  return new Promise((resolve, reject) => {
    exec(command, (error, stdout, stderr) => {
      if (error) {
        reject(`Error: ${error}`);
        return;
      }
      if (stderr) {
        reject(`stderr: ${stderr}`);
        return;
      }

      // Assuming the output is a JSON string, parse it as an object
      let parsedJson;
      try {
        parsedJson = JSON.parse(stdout);
      } catch (e) {
        reject(`Error parsing JSON: ${e}`);
        return;
      }

      // Resolve the promise with the parsed JSON object
      resolve(parsedJson);
    });
  });
}

const usersCommand =
  "wp --url=academy.codingblackfemales.lndo.site user list --fields=ID,user_login --orderby=ID --format=json";

runShellCommand(usersCommand)
  .then((newUsers) => {
    importJSON(path.join(__dirname, "users.json"))
      .then((data) => {
        const oldUsers = data.map((user) => {
          return { ID: parseInt(user.ID, 10), user_login: user.user_login };
        });
        const directoryPath = path.join(
          path.dirname(__dirname),
          "web/app/uploads/sites/2/avatars"
        );
        const userMap = getMappedUsers(oldUsers, newUsers);

        renameDirectories(directoryPath, userMap);
      })
      .catch((error) => {
        console.error(error);
      });
  })
  .catch((error) => {
    console.error(error);
  });
